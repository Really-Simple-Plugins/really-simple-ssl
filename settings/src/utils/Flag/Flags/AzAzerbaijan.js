import * as React from "react";
const SvgAzAzerbaijan = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="AZ_-_Azerbaijan_svg__a"
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
    <g mask="url(#AZ_-_Azerbaijan_svg__a)">
      <path
        fill="#AF0100"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="AZ_-_Azerbaijan_svg__b"
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
        fillRule="evenodd"
        clipRule="evenodd"
        mask="url(#AZ_-_Azerbaijan_svg__b)"
      >
        <path fill="#3CA5D9" d="M0 0v4h16V0H0Z" />
        <path fill="#73BE4A" d="M0 8v4h16V8H0Z" />
        <path
          fill="#F7FCFF"
          d="M8.58 7.453c-.673-.155-1.2-.684-1.193-1.461a1.53 1.53 0 0 1 1.217-1.51c.74-.167 1.392.185 1.392.185-.204-.454-.915-.772-1.498-.77-1.085.002-2.243.83-2.254 2.096C6.232 7.304 7.481 8.05 8.58 8.047c.88-.002 1.299-.57 1.386-.81 0 0-.71.372-1.384.216Zm.429-.743.588-.409.588.41-.208-.686.57-.433-.715-.014-.235-.677-.235.677-.716.014.57.433-.207.685Z"
        />
      </g>
    </g>
  </svg>
);
export default SvgAzAzerbaijan;
