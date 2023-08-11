import * as React from "react";
const SvgEtEthiopia = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="ET_-_Ethiopia_svg__a"
      width={16}
      height={12}
      x={0}
      y={0}
      maskUnits="userSpaceOnUse"
      style={{
        maskType: "luminance",
      }}
    >
      <path fill="#fff" d="M0 0h16v12H0z" />
    </mask>
    <g mask="url(#ET_-_Ethiopia_svg__a)">
      <path
        fill="#FECA00"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="ET_-_Ethiopia_svg__b"
        width={16}
        height={12}
        x={0}
        y={0}
        maskUnits="userSpaceOnUse"
        style={{
          maskType: "luminance",
        }}
      >
        <path
          fill="#fff"
          fillRule="evenodd"
          d="M0 0v12h16V0H0Z"
          clipRule="evenodd"
        />
      </mask>
      <g mask="url(#ET_-_Ethiopia_svg__b)">
        <path
          fill="#5EAA22"
          fillRule="evenodd"
          d="M0 0v4h16V0H0Z"
          clipRule="evenodd"
        />
        <path
          fill="#E31D1C"
          fillRule="evenodd"
          d="M0 8v4h16V8H0Z"
          clipRule="evenodd"
        />
        <path
          fill="#2B77B8"
          fillRule="evenodd"
          d="M8 9a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
          clipRule="evenodd"
        />
        <path
          stroke="#FECA00"
          strokeWidth={0.75}
          d="m8 7-1.381.463.43-1.154-.937-1.118h1.3L8 4l.588 1.191h1.328l-.965 1.118.343 1.154L8 7Z"
          clipRule="evenodd"
        />
        <path
          stroke="#2B77B8"
          strokeWidth={0.5}
          d="m7.848 6.017-1.042 2.18M7.684 5.74h-2M8.06 6.419l1.955.902M8.483 5.937l1.415-1.549"
        />
      </g>
    </g>
  </svg>
);
export default SvgEtEthiopia;
