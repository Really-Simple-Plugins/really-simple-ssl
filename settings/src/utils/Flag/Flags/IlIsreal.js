import * as React from "react";
const SvgIlIsreal = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="IL_-_Isreal_svg__a"
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
    <g mask="url(#IL_-_Isreal_svg__a)">
      <path
        fill="#F7FCFF"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="IL_-_Isreal_svg__b"
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
      <g
        fill="#3D58DB"
        fillRule="evenodd"
        clipRule="evenodd"
        mask="url(#IL_-_Isreal_svg__b)"
      >
        <path d="M0 2v1.783h16V2H0ZM0 8.174V10h16V8.174H0Z" />
        <path d="M5.69 7.47h4.69L8.057 3.46 5.691 7.47Zm3.735-.55H6.654l1.4-2.371 1.371 2.37Z" />
        <path d="M5.546 4.463h4.794L8.068 8.485 5.546 4.463Zm3.852.55H6.54L8.043 7.41l1.355-2.398Z" />
      </g>
    </g>
  </svg>
);
export default SvgIlIsreal;
