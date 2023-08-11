import * as React from "react";
const SvgBoBolivia = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="BO_-_Bolivia_svg__a"
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
    <g mask="url(#BO_-_Bolivia_svg__a)">
      <path
        fill="#FECA00"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="BO_-_Bolivia_svg__b"
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
      <g mask="url(#BO_-_Bolivia_svg__b)">
        <path
          fill="#DB501C"
          fillRule="evenodd"
          d="M0 0v4h16V0H0Z"
          clipRule="evenodd"
        />
        <path
          fill="#5EAA22"
          fillRule="evenodd"
          d="M0 8v4h16V8H0Z"
          clipRule="evenodd"
        />
        <path
          stroke="#DB501C"
          strokeWidth={0.75}
          d="M5.824 5.63S5.638 7.535 7.33 7.535h1.261s1.781-.117 1.574-1.905"
        />
        <path
          fill="#FECA00"
          stroke="#68B9E8"
          strokeWidth={0.75}
          d="M9.125 5.9a1.125 1.125 0 1 1-2.25 0 1.125 1.125 0 0 1 2.25 0Z"
        />
        <path
          fill="#DB501C"
          fillRule="evenodd"
          d="M8 6a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1Z"
          clipRule="evenodd"
        />
        <path
          fill="#5EAA22"
          fillRule="evenodd"
          d="M8.05 6.8c.47 0 .85-.18.85-.4 0-.22-.38-.4-.85-.4s-.85.18-.85.4c0 .22.38.4.85.4Z"
          clipRule="evenodd"
        />
        <path
          fill="#674F28"
          fillRule="evenodd"
          d="M8.05 5c.663 0 1.2-.18 1.2-.4 0-.22-.537-.4-1.2-.4-.663 0-1.2.18-1.2.4 0 .22.537.4 1.2.4Z"
          clipRule="evenodd"
        />
      </g>
    </g>
  </svg>
);
export default SvgBoBolivia;
